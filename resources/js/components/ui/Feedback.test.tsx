import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { EmptyState, ErrorState } from './Feedback';

describe('EmptyState and ErrorState headings', () => {
    it('default to h3 and can be raised to h2 so heading order stays valid', () => {
        const { rerender } = render(<EmptyState title="Nothing here" />);
        expect(screen.getByRole('heading', { level: 3, name: 'Nothing here' })).toBeInTheDocument();
        rerender(<EmptyState title="Nothing here" headingLevel="h2" />);
        expect(screen.getByRole('heading', { level: 2, name: 'Nothing here' })).toBeInTheDocument();
        rerender(<ErrorState headingLevel="h2" />);
        expect(screen.getByRole('heading', { level: 2, name: 'Something went wrong' })).toBeInTheDocument();
    });
});
